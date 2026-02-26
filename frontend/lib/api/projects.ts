import useSWR, { mutate as globalMutate } from "swr";

import type {
  Project,
  ProjectListResponse,
  ProjectListMeta,
  ProjectCreatePayload,
  ProjectUpdatePayload,
  ProjectMetrics,
  AggregateMetrics,
  ProjectBreakdown,
} from "@/lib/types/projects";

const API_BASE = "/api/v1";

// Storage key for persisting current project
const CURRENT_PROJECT_KEY = "seoindicator_current_project";

// Normalize API response to consistent format
const normalizeProject = (item: any): Project => ({
  id: item.id,
  name: item.name ?? "",
  domain: item.domain ?? null,
  notes: item.notes ?? null,
  status: item.archived_at ? "archived" : "active",
  isDefault: item.is_default ?? item.isDefault ?? false,
  archivedAt: item.archived_at ?? item.archivedAt ?? null,
  createdAt: item.created_at ?? item.createdAt ?? "",
  updatedAt: item.updated_at ?? item.updatedAt ?? "",
  keywordsCount: item.keywords_count ?? item.keywordsCount,
  avgPosition: item.avg_position ?? item.avgPosition,
  visibility: item.visibility,
  positionChange: item.position_change ?? item.positionChange,
  visibilityChange: item.visibility_change ?? item.visibilityChange,
});

const normalizeProjectList = (payload: any): ProjectListResponse => {
  if (Array.isArray(payload)) {
    return { data: payload.map(normalizeProject) };
  }

  const rawData = payload?.data ?? [];
  return {
    data: Array.isArray(rawData) ? rawData.map(normalizeProject) : [],
    meta: payload?.meta ?? payload?.pagination ?? undefined,
  };
};

// Fetcher for SWR
const fetcher = async (url: string): Promise<ProjectListResponse> => {
  const response = await fetch(url);
  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? `Failed to fetch: ${response.statusText}`);
  }
  const payload = await response.json();
  return normalizeProjectList(payload);
};

// Get stored project ID from localStorage
export function getStoredProjectId(): number | null {
  if (typeof window === "undefined") return null;
  const stored = localStorage.getItem(CURRENT_PROJECT_KEY);
  return stored ? parseInt(stored, 10) : null;
}

// Store project ID in localStorage
export function storeProjectId(projectId: number): void {
  if (typeof window === "undefined") return;
  localStorage.setItem(CURRENT_PROJECT_KEY, String(projectId));
}

// Clear stored project ID
export function clearStoredProjectId(): void {
  if (typeof window === "undefined") return;
  localStorage.removeItem(CURRENT_PROJECT_KEY);
}

// Hook for fetching projects list
export function useProjects(includeArchived = false) {
  const query = includeArchived ? "?includeArchived=true" : "";
  const { data, error, isLoading, mutate } = useSWR<ProjectListResponse>(
    `${API_BASE}/projects${query}`,
    fetcher
  );

  return {
    projects: data?.data ?? [],
    meta: data?.meta,
    isLoading,
    error,
    mutate,
  };
}

// Hook for fetching a single project
export function useProject(id: number | null | undefined) {
  const key = id ? `${API_BASE}/projects/${id}` : null;

  const { data, error, isLoading, mutate } = useSWR<Project>(
    key,
    async (url: string) => {
      const response = await fetch(url);
      if (!response.ok) {
        if (response.status === 404) {
          throw new Error("Project not found");
        }
        throw new Error(`Failed to fetch project: ${response.statusText}`);
      }
      const payload = await response.json();
      return normalizeProject(payload?.data ?? payload);
    }
  );

  return {
    project: data,
    error,
    isLoading,
    mutate,
  };
}

// Hook for fetching project metrics
export function useProjectMetrics(projectId: number | null | undefined) {
  const key = projectId ? `${API_BASE}/projects/${projectId}/metrics` : null;

  const { data, error, isLoading, mutate } = useSWR<ProjectMetrics>(
    key,
    async (url: string) => {
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`Failed to fetch metrics: ${response.statusText}`);
      }
      const payload = await response.json();
      return {
        keywordsCount: payload?.keywords_count ?? payload?.keywordsCount ?? 0,
        avgPosition: payload?.avg_position ?? payload?.avgPosition ?? null,
        visibility: payload?.visibility ?? 0,
        change: {
          visibility: payload?.change?.visibility ?? null,
          avgPosition: payload?.change?.avg_position ?? payload?.change?.avgPosition ?? null,
        },
      };
    }
  );

  return {
    metrics: data,
    error,
    isLoading,
    mutate,
  };
}

// Create a new project
export async function createProject(payload: ProjectCreatePayload): Promise<Project> {
  const response = await fetch(`${API_BASE}/projects`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      name: payload.name,
      domain: payload.domain ?? null,
      notes: payload.notes ?? null,
    }),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to create project");
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === "string" && key.includes("/projects"));
  return normalizeProject(data?.data ?? data);
}

// Update a project
export async function updateProject(id: number, payload: ProjectUpdatePayload): Promise<Project> {
  const response = await fetch(`${API_BASE}/projects/${id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to update project");
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === "string" && key.includes("/projects"));
  return normalizeProject(data?.data ?? data);
}

// Delete a project
export async function deleteProject(id: number): Promise<void> {
  const response = await fetch(`${API_BASE}/projects/${id}?confirm=true`, {
    method: "DELETE",
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to delete project");
  }

  clearStoredProjectId();
  await globalMutate((key) => typeof key === "string" && key.includes("/projects"));
}

// Archive a project
export async function archiveProject(id: number): Promise<Project> {
  const response = await fetch(`${API_BASE}/projects/${id}/archive`, {
    method: "POST",
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to archive project");
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === "string" && key.includes("/projects"));
  return normalizeProject(data?.data ?? data);
}

// Restore an archived project
export async function restoreProject(id: number): Promise<Project> {
  const response = await fetch(`${API_BASE}/projects/${id}/restore`, {
    method: "POST",
  });

  if (!response.ok) {
    const errorBody = await response.json().catch(() => null);
    throw new Error(errorBody?.message ?? "Failed to restore project");
  }

  const data = await response.json();
  await globalMutate((key) => typeof key === "string" && key.includes("/projects"));
  return normalizeProject(data?.data ?? data);
}

// Fetch aggregate metrics for all projects
export async function fetchAggregateMetrics(): Promise<{
  aggregate: AggregateMetrics;
  projects: ProjectBreakdown[];
}> {
  const response = await fetch(`${API_BASE}/projects/aggregate`);

  if (!response.ok) {
    throw new Error("Failed to fetch aggregate metrics");
  }

  const payload = await response.json();
  return {
    aggregate: {
      totalProjects: payload?.aggregate?.total_projects ?? payload?.aggregate?.totalProjects ?? 0,
      totalKeywords: payload?.aggregate?.total_keywords ?? payload?.aggregate?.totalKeywords ?? 0,
      avgPosition: payload?.aggregate?.avg_position ?? payload?.aggregate?.avgPosition ?? null,
      totalVisibility: payload?.aggregate?.total_visibility ?? payload?.aggregate?.totalVisibility ?? 0,
      visibilityChange: payload?.aggregate?.visibility_change ?? payload?.aggregate?.visibilityChange ?? null,
    },
    projects: (payload?.projects ?? []).map((p: any) => ({
      ...normalizeProject(p),
      metrics: {
        keywordsCount: p.keywords_count ?? p.keywordsCount ?? 0,
        avgPosition: p.avg_position ?? p.avgPosition ?? null,
        visibility: p.visibility ?? 0,
        change: {
          visibility: p.change?.visibility ?? p.visibility_change ?? null,
          avgPosition: p.change?.avg_position ?? p.position_change ?? null,
        },
      },
    })),
  };
}

// Default meta for pagination
export const defaultProjectMeta: ProjectListMeta = {
  total: 0,
  page: 1,
  perPage: 20,
};
