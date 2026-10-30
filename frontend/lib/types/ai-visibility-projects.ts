/**
 * TASK-34-8: AI Visibility Projects Types
 * 
 * Type definitions for the AI Visibility project list page.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

// ============================================
// Project Types
// ============================================

export interface AIVisibilityProjectListItem {
  id: number;
  brand_name: string;
  is_active: boolean;
  last_queried_at: string | null;
  mention_rate: number;
  keywords_count: number;
  competitors_count: number;
  created_at: string;
}

// ============================================
// API Response Types
// ============================================

export interface ProjectsApiResponse {
  data: AIVisibilityProjectListItem[];
  meta: {
    current_page: number;
    total: number;
    per_page: number;
    last_page: number;
  };
}

// ============================================
// Hook Parameter Types
// ============================================

export interface UseProjectsParams {
  page?: number;
  status?: 'all' | 'active' | 'inactive';
  search?: string;
  sort?: 'created_at' | 'brand_name' | 'last_queried_at' | 'mention_rate';
}

// ============================================
// Hook Return Types
// ============================================

export interface UseProjectsReturn {
  data: ProjectsApiResponse | null;
  isLoading: boolean;
  error: Error | null;
  refetch: () => void;
}

export interface UseDeleteProjectReturn {
  mutate: (projectId: number) => void;
  mutateAsync: (projectId: number) => Promise<void>;
  isLoading: boolean;
  error: Error | null;
}

export interface UseRunQueryReturn {
  mutate: (projectId: number) => void;
  mutateAsync: (projectId: number) => Promise<{ success: boolean; job_id?: string }>;
  isLoading: boolean;
  error: Error | null;
}

// ============================================
// Component Props Types
// ============================================

export interface ProjectCardProps {
  project: AIVisibilityProjectListItem;
  onViewDashboard?: () => void;
  onRunQuery?: () => void;
  onEditProject?: () => void;
  onDelete?: () => void;
  isLoading?: boolean;
  className?: string;
}

export interface ProjectListProps {
  className?: string;
  onCreateProject?: () => void;
  onViewDashboard?: (projectId: number) => void;
  onEditProject?: (projectId: number) => void;
}

export interface EmptyProjectsStateProps {
  onCreateProject?: () => void;
  className?: string;
}

export interface DeleteProjectModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => Promise<void>;
  projectName: string;
  isLoading?: boolean;
}
