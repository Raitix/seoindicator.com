/**
 * TypeScript types for Backlinks Dashboard Components
 * Task: TASK-23-13 - Backlinks Dashboard Components
 */

/**
 * Summary metrics for backlink overview
 */
export interface BacklinkSummary {
  totalBacklinks: number;
  referringDomains: number;
  dofollowRatio: number;
  avgDomainTrust: number;
  growthRate: number;
  profileScore: number;
}

/**
 * Referring domain with backlink metrics
 */
export interface ReferringDomain {
  domain: string;
  backlinks_count: number;
  domain_trust_score: number;
  first_seen_at: string;
  last_seen_at: string | null;
}

/**
 * Anchor text with distribution metrics
 */
export interface AnchorText {
  anchor_text: string;
  backlinks_count: number;
  percentage: number;
  is_branded: boolean;
  is_exact_match: boolean;
}

/**
 * Historical backlink data point
 */
export interface BacklinkHistoryPoint {
  date: string;
  backlinks: number;
  domains: number;
}

/**
 * Full history data from API (matches backend response)
 */
export interface BacklinkHistoryData {
  date: string;
  total_backlinks: number;
  referring_domains_count: number;
  dofollow_count: number;
  nofollow_count: number;
  avg_domain_trust: number;
  anchor_diversity_score: number;
}

/**
 * Pagination metadata for domains list
 */
export interface DomainsMeta {
  total: number;
  page: number;
  perPage: number;
  totalPages: number;
}

/**
 * Sort options for domains table
 */
export type DomainsSortField = 'domain' | 'backlinks_count' | 'domain_trust_score' | 'first_seen_at';
export type SortOrder = 'asc' | 'desc';

/**
 * Date range for history chart
 */
export interface DateRange {
  from: string;
  to: string;
}

/**
 * API response types
 */
export interface BacklinksOverviewResponse {
  success: boolean;
  data: {
    summary: BacklinkSummary;
    recentBacklinks: ReferringDomain[];
    topReferringDomains: ReferringDomain[];
  };
}

export interface DomainsResponse {
  data: ReferringDomain[];
  meta: DomainsMeta;
}

export interface AnchorsResponse {
  data: AnchorText[];
}

export interface HistoryResponse {
  data: BacklinkHistoryData[];
  meta?: {
    from: string;
    to: string;
  };
}

export interface SyncResponse {
  jobId: string;
  status: 'queued' | 'processing' | 'completed' | 'failed';
  estimatedTime?: number;
}

/**
 * Component props types
 */
export interface BacklinksDashboardProps {
  projectId: number;
  className?: string;
}

export interface BacklinkOverviewCardsProps {
  summary: BacklinkSummary | null;
  loading?: boolean;
  className?: string;
}

export interface ReferringDomainsTableProps {
  projectId: number;
  domains?: ReferringDomain[];
  meta?: DomainsMeta;
  loading?: boolean;
  onSort?: (field: DomainsSortField, order: SortOrder) => void;
  onPageChange?: (page: number) => void;
  sortField?: DomainsSortField;
  sortOrder?: SortOrder;
  currentPage?: number;
  className?: string;
}

export interface AnchorTextDistributionProps {
  anchors: AnchorText[];
  loading?: boolean;
  className?: string;
}

export interface BacklinkHistoryChartProps {
  projectId: number;
  dateRange?: DateRange;
  onDateRangeChange?: (range: DateRange) => void;
  className?: string;
}
