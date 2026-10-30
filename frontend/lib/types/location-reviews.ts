export type ReviewSentiment = "positive" | "neutral" | "negative";

export interface LocationReviewResponse {
  text: string;
  respondedAt: string | null;
  responderName: string | null;
}

export interface LocationReview {
  id: string;
  numericId: number | null;
  gbpReviewId: string | null;
  author: string;
  avatarUrl: string | null;
  rating: number;
  text: string;
  sentiment: ReviewSentiment;
  reviewedAt: string;
  hasResponse: boolean;
  response: LocationReviewResponse | null;
  googleUrl: string | null;
  isLocalGuide: boolean;
  reviewCount: number | null;
}

export interface ReviewStatsDetailed {
  averageRating: number;
  previousRating: number | null;
  totalCount: number;
  previousCount: number | null;
  ratingDistribution: Record<number, number>;
  sentimentBreakdown: Record<ReviewSentiment, number>;
  responseRate: number;
  averageResponseTimeHours: number | null;
}

export interface ReviewListMeta {
  currentPage: number;
  perPage: number;
  total: number;
  lastPage: number;
}

export interface ReviewListResponse {
  reviews: LocationReview[];
  meta: ReviewListMeta;
}

export type ReviewSortValue = "-date" | "date" | "-rating" | "rating";
export type ReviewResponseFilter = "all" | "responded" | "unresponded";

export interface ReviewFilterState {
  ratings: number[];
  sentiment: "all" | ReviewSentiment;
  responseStatus: ReviewResponseFilter;
  startDate: string;
  endDate: string;
  sort: ReviewSortValue;
}

export const REVIEW_PAGE_SIZE = 20;

export const defaultReviewFilters: ReviewFilterState = {
  ratings: [],
  sentiment: "all",
  responseStatus: "all",
  startDate: "",
  endDate: "",
  sort: "-date",
};
