export type ProjectStatus = "active" | "archived";

export interface Project {
  id: number;
  name: string;
  domain: string | null;
  notes: string | null;
  status: ProjectStatus;
  isDefault: boolean;
  archivedAt: string | null;
  createdAt: string;
  updatedAt: string;
  // Optional computed fields for display
  keywordsCount?: number;
  avgPosition?: number;
  visibility?: number;
  positionChange?: number | null;
  visibilityChange?: number | null;
}

export interface ProjectListMeta {
  total: number;
  page: number;
  perPage: number;
  hasMore?: boolean;
}

export interface ProjectListResponse {
  data: Project[];
  meta?: ProjectListMeta;
}

export interface ProjectCreatePayload {
  name: string;
  domain?: string | null;
  notes?: string | null;
}

export interface ProjectUpdatePayload {
  name?: string;
  domain?: string | null;
  notes?: string | null;
}

export interface ProjectMetrics {
  keywordsCount: number;
  avgPosition: number | null;
  visibility: number;
  change: {
    visibility: number | null;
    avgPosition: number | null;
  };
}

export interface AggregateMetrics {
  totalProjects: number;
  totalKeywords: number;
  avgPosition: number | null;
  totalVisibility: number;
  visibilityChange: number | null;
}

export interface ProjectBreakdown extends Project {
  metrics: ProjectMetrics;
}

export interface ProjectsBreakdownResponse {
  aggregate: AggregateMetrics;
  projects: ProjectBreakdown[];
}

// Context types
export interface ProjectContextValue {
  projects: Project[];
  currentProject: Project | null;
  isLoading: boolean;
  error: Error | null;
  switchProject: (projectId: number) => Promise<void>;
  createProject: (payload: ProjectCreatePayload) => Promise<Project>;
  updateProject: (id: number, payload: ProjectUpdatePayload) => Promise<Project>;
  deleteProject: (id: number) => Promise<void>;
  archiveProject: (id: number) => Promise<void>;
  restoreProject: (id: number) => Promise<void>;
  refreshProjects: () => Promise<void>;
}
