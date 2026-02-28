// GSC Data Types for TASK-12-10

export interface GSCMetric {
  query: string;
  clicks: number;
  impressions: number;
  ctr: number;
  avg_position: number;
}

export interface GSCDataPoint extends GSCMetric {
  date: string;
  page?: string;
}

export interface GSCOpportunityKeyword extends GSCMetric {
  opportunity_score: number;
  expected_ctr: number;
}

export interface GSCTrendData {
  date: string;
  clicks: number;
  impressions: number;
  ctr: number;
  avg_position: number;
}

export interface DateRange {
  from: string;
  to: string;
}

export interface GSCDataResponse {
  data: GSCMetric[];
  meta: {
    total: number;
    date_range: DateRange;
  };
}

export interface GSCOpportunitiesResponse {
  data: GSCOpportunityKeyword[];
  meta: {
    total: number;
  };
}

export interface GSCTrendResponse {
  data: GSCTrendData[];
  meta: {
    date_range: DateRange;
  };
}

export type SortDirection = 'asc' | 'desc';

export interface SortConfig {
  key: keyof GSCMetric;
  direction: SortDirection;
}

// GSC Connection Types for TASK-12-9

export interface GSCProperty {
  id: number;
  property_url: string;
  site_url: string;
  connected_at: string;
  status?: 'active' | 'expired' | 'revoked';
}

export interface GSCPropertiesResponse {
  data: GSCProperty[];
}

export interface GSCConnectRequest {
  authorization_code: string;
}

export interface GSCConnectResponse {
  success: boolean;
  property_url: string;
}

export interface GSCDisconnectResponse {
  success: boolean;
}
