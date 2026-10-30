/**
 * TASK-34-8: AI Visibility Projects API Hooks
 * 
 * React Query hooks for fetching and mutating AI Visibility projects.
 * 
 * @see PROJECT-SEOINDICATOR/TASKS/TASK-34-8.md
 */

'use client';

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useRouter } from 'next/navigation';
import type {
  ProjectsApiResponse,
  UseProjectsParams,
  UseProjectsReturn,
  UseDeleteProjectReturn,
  UseRunQueryReturn,
} from '@/lib/types/ai-visibility-projects';

// ============================================
// API Functions
// ============================================

async function fetchProjects(params: UseProjectsParams): Promise<ProjectsApiResponse> {
  const queryParams = new URLSearchParams({
    page: String(params.page || 1),
    sort: params.sort || 'created_at',
  });

  if (params.status && params.status !== 'all') {
    queryParams.set('status', params.status);
  }

  if (params.search) {
    queryParams.set('search', params.search);
  }

  const response = await fetch(`/api/v1/ai-visibility/projects?${queryParams}`);

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error('Unauthenticated');
    }
    throw new Error('Failed to fetch projects');
  }

  return response.json();
}

async function deleteProject(projectId: number): Promise<void> {
  const response = await fetch(`/api/v1/ai-visibility/projects/${projectId}`, {
    method: 'DELETE',
  });

  if (!response.ok) {
    if (response.status === 403) {
      throw new Error('Forbidden - you do not have permission to delete this project');
    }
    if (response.status === 404) {
      throw new Error('Project not found');
    }
    throw new Error('Failed to delete project');
  }
}

async function runProjectQuery(projectId: number): Promise<{ success: boolean; job_id?: string }> {
  const response = await fetch(`/api/v1/ai-visibility/projects/${projectId}/run`, {
    method: 'POST',
  });

  if (!response.ok) {
    if (response.status === 429) {
      throw new Error('Rate limit exceeded - please try again later');
    }
    if (response.status === 403) {
      throw new Error('Query limit exceeded for your plan');
    }
    throw new Error('Failed to run query');
  }

  return response.json();
}

// ============================================
// Query Keys
// ============================================

const projectKeys = {
  all: ['ai-visibility', 'projects'] as const,
  lists: () => [...projectKeys.all, 'list'] as const,
  list: (params: UseProjectsParams) => [...projectKeys.lists(), params] as const,
};

// ============================================
// useProjects Hook
// ============================================

export function useProjects(params: UseProjectsParams = {}): UseProjectsReturn {
  const queryClient = useQueryClient();

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: projectKeys.list(params),
    queryFn: () => fetchProjects(params),
    staleTime: 30 * 1000, // 30 seconds
  });

  return {
    data: data ?? null,
    isLoading,
    error: error as Error | null,
    refetch: () => {
      queryClient.invalidateQueries({ queryKey: projectKeys.lists() });
    },
  };
}

// ============================================
// useDeleteProject Hook
// ============================================

export function useDeleteProject(): UseDeleteProjectReturn {
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: deleteProject,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: projectKeys.lists() });
    },
  });

  return {
    mutate: (projectId: number) => mutation.mutate(projectId),
    mutateAsync: (projectId: number) => mutation.mutateAsync(projectId),
    isLoading: mutation.isPending,
    error: mutation.error as Error | null,
  };
}

// ============================================
// useRunQuery Hook
// ============================================

export function useRunQuery(): UseRunQueryReturn {
  const queryClient = useQueryClient();

  const mutation = useMutation({
    mutationFn: runProjectQuery,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: projectKeys.lists() });
    },
  });

  return {
    mutate: (projectId: number) => mutation.mutate(projectId),
    mutateAsync: (projectId: number) => mutation.mutateAsync(projectId),
    isLoading: mutation.isPending,
    error: mutation.error as Error | null,
  };
}

export { projectKeys };
