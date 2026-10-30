/**
 * Annotation Types
 *
 * Types for keyword-level SEO event annotations.
 *
 * @see SPEC-009
 */

export type AnnotationCategory =
  | "algorithm-update"
  | "content-update"
  | "technical-change"
  | "link-building"
  | "competition"
  | "seasonal"
  | "manual-action"
  | "recovery"
  | "other";

export interface Annotation {
  id: number;
  keyword_id: number;
  project_id: number;
  date: string;
  title: string;
  description: string | null;
  category: AnnotationCategory;
  created_at: string;
  updated_at: string;
}

export interface AnnotationListMeta {
  total: number;
  page: number;
  perPage: number;
  last_page?: number;
}

export interface AnnotationListResponse {
  data: Annotation[];
  meta?: AnnotationListMeta;
}

export interface CreateAnnotationDTO {
  date: string;
  title: string;
  description?: string;
  category?: AnnotationCategory;
}

export interface UpdateAnnotationDTO {
  date?: string;
  title?: string;
  description?: string | null;
  category?: AnnotationCategory;
}

export interface AnnotationListProps {
  keywordId: number;
  annotations: Annotation[];
  onEdit: (annotation: Annotation) => void;
  onDelete: (annotationId: number) => void;
  isLoading?: boolean;
  error?: Error | null;
}

export interface AnnotationFormProps {
  keywordId: number;
  annotation?: Annotation;
  onSave: (data: CreateAnnotationDTO) => void | Promise<void>;
  onCancel: () => void;
}

/**
 * Category options for dropdown
 */
export const ANNOTATION_CATEGORIES: Array<{
  value: AnnotationCategory;
  label: string;
  icon: string;
}> = [
  { value: "algorithm-update", label: "Algorithm Update", icon: "🔄" },
  { value: "content-update", label: "Content Update", icon: "📝" },
  { value: "technical-change", label: "Technical Change", icon: "🔧" },
  { value: "link-building", label: "Link Building", icon: "🔗" },
  { value: "competition", label: "Competitor Activity", icon: "👀" },
  { value: "seasonal", label: "Seasonal Trend", icon: "📅" },
  { value: "manual-action", label: "Manual Action", icon: "⚠️" },
  { value: "recovery", label: "Recovery", icon: "✅" },
  { value: "other", label: "Other", icon: "📌" },
];
