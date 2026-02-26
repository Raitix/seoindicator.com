export type KeywordStatus = "active" | "paused" | "deleted";

// SERP Feature Types
export type SERPFeatureType =
  | "featured_snippet"
  | "local_pack"
  | "image_pack"
  | "video"
  | "people_also_ask"
  | "knowledge_panel"
  | "sitelinks"
  | "reviews"
  | "faq"
  | "product";

export interface SERPFeature {
  type: SERPFeatureType;
  isPresent: boolean;
  position?: number;
  url?: string;
  title?: string;
  snippet?: string;
}

export interface SERPFeaturesMap {
  featured_snippet: boolean;
  local_pack: boolean;
  image_pack: boolean;
  video: boolean;
  people_also_ask: boolean;
  knowledge_panel: boolean;
  sitelinks: boolean;
  reviews: boolean;
  faq: boolean;
  product: boolean;
}

export interface Keyword {
  id: number;
  keyword: string;
  status: KeywordStatus;
  currentPosition: number | null;
  previousPosition: number | null;
  positionChange: number | null;
  lastCheckedAt: string | null;
  targetUrl?: string | null;
  searchEngine?: string | null;
  location?: string | null;
  device?: string | null;
  serpFeatures?: Partial<SERPFeaturesMap> | null;
}

export interface KeywordListMeta {
  total: number;
  page: number;
  perPage: number;
}

export interface KeywordListResponse {
  data: Keyword[];
  meta?: KeywordListMeta;
}

export interface KeywordCreatePayload {
  keyword: string;
  targetUrl?: string;
  searchEngine?: string;
  location?: string;
  device?: string;
}

export interface KeywordUpdatePayload {
  targetUrl?: string;
  status?: KeywordStatus;
}
