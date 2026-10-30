/**
 * Tests for AI Visibility Projects API Hooks
 * Task: TASK-34-8
 * Feature: SPEC-034 - AI Visibility Tracking
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor, act } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import React from 'react';

// Import actual hooks
import {
  useProjects,
  useDeleteProject,
  useRunQuery,
  projectKeys,
} from '@/lib/hooks/useAIVisibilityProjects';

// Mock fetch globally
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock router
vi.mock('next/navigation', () => ({
  useRouter: () => ({
    push: vi.fn(),
    refresh: vi.fn()
  })
}));

// Test wrapper with QueryClient
const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        gcTime: 0
      },
      mutations: {
        retry: false
      }
    }
  });

  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  );
};

// Test data
const mockProjectsResponse = {
  data: [
    {
      id: 1,
      brand_name: 'Acme Corp',
      is_active: true,
      last_queried_at: '2026-03-07T10:00:00Z',
      mention_rate: 0.45,
      keywords_count: 10,
      competitors_count: 3,
      created_at: '2026-03-01T00:00:00Z'
    },
    {
      id: 2,
      brand_name: 'TechStart Inc',
      is_active: false,
      last_queried_at: null,
      mention_rate: 0,
      keywords_count: 5,
      competitors_count: 2,
      created_at: '2026-03-05T00:00:00Z'
    }
  ],
  meta: {
    current_page: 1,
    total: 2,
    per_page: 10,
    last_page: 1
  }
};

describe('AI Visibility Projects API Hooks', () => {
  let wrapper: ReturnType<typeof createWrapper>;

  beforeEach(() => {
    vi.clearAllMocks();
    mockFetch.mockReset();
    wrapper = createWrapper();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('API-FETCH: useProjects Hook', () => {
    it('should fetch projects with default parameters', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockProjectsResponse)
      });

      const { result } = renderHook(() => useProjects(), { wrapper });

      // Initial state
      expect(result.current.isLoading).toBe(true);
      expect(result.current.data).toBeNull();

      // Wait for fetch to complete
      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.data).toEqual(mockProjectsResponse);
      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/ai-visibility/projects')
      );
    });

    it('should pass page parameter to API', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockProjectsResponse)
      });

      renderHook(() => useProjects({ page: 2 }), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('page=2')
        );
      });
    });

    it('should pass status filter to API', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockProjectsResponse)
      });

      renderHook(() => useProjects({ status: 'active' }), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('status=active')
        );
      });
    });

    it('should pass search query to API', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockProjectsResponse)
      });

      renderHook(() => useProjects({ search: 'Acme' }), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('search=Acme')
        );
      });
    });

    it('should pass sort parameter to API', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockProjectsResponse)
      });

      renderHook(() => useProjects({ sort: 'brand_name' }), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledWith(
          expect.stringContaining('sort=brand_name')
        );
      });
    });

    it('should handle 401 unauthenticated error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401
      });

      const { result } = renderHook(() => useProjects(), { wrapper });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.error).toBeInstanceOf(Error);
      expect(result.current.error?.message).toBe('Unauthenticated');
    });

    it('should handle generic API error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500
      });

      const { result } = renderHook(() => useProjects(), { wrapper });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.error).toBeInstanceOf(Error);
      expect(result.current.error?.message).toBe('Failed to fetch projects');
    });

    it('should provide refetch function', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockProjectsResponse)
      });

      const { result } = renderHook(() => useProjects(), { wrapper });

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.refetch).toBeDefined();
      expect(typeof result.current.refetch).toBe('function');
    });
  });

  describe('API-MUTATIONS: useDeleteProject Hook', () => {
    it('should call delete API with project ID', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true })
      });

      const { result } = renderHook(() => useDeleteProject(), { wrapper });

      await act(async () => {
        await result.current.mutateAsync(1);
      });

      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/ai-visibility/projects/1'),
        expect.objectContaining({ method: 'DELETE' })
      );
    });

    it('should handle 403 forbidden error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 403
      });

      const { result } = renderHook(() => useDeleteProject(), { wrapper });

      await act(async () => {
        try {
          await result.current.mutateAsync(1);
        } catch (error) {
          expect(error).toBeInstanceOf(Error);
          expect((error as Error).message).toContain('permission');
        }
      });
    });

    it('should handle 404 not found error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 404
      });

      const { result } = renderHook(() => useDeleteProject(), { wrapper });

      await act(async () => {
        try {
          await result.current.mutateAsync(1);
        } catch (error) {
          expect(error).toBeInstanceOf(Error);
          expect((error as Error).message).toContain('not found');
        }
      });
    });

    it('should provide mutate function', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true })
      });

      const { result } = renderHook(() => useDeleteProject(), { wrapper });

      expect(result.current.mutate).toBeDefined();
      expect(typeof result.current.mutate).toBe('function');
    });

    it('should provide isLoading state', async () => {
      const { result } = renderHook(() => useDeleteProject(), { wrapper });

      expect(result.current.isLoading).toBe(false);
    });
  });

  describe('API-MUTATIONS: useRunQuery Hook', () => {
    it('should call run query API with project ID', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true, job_id: 'job-123' })
      });

      const { result } = renderHook(() => useRunQuery(), { wrapper });

      await act(async () => {
        const response = await result.current.mutateAsync(1);
        expect(response.success).toBe(true);
        expect(response.job_id).toBe('job-123');
      });

      expect(mockFetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/v1/ai-visibility/projects/1/run'),
        expect.objectContaining({ method: 'POST' })
      );
    });

    it('should handle 429 rate limit error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 429
      });

      const { result } = renderHook(() => useRunQuery(), { wrapper });

      await act(async () => {
        try {
          await result.current.mutateAsync(1);
        } catch (error) {
          expect(error).toBeInstanceOf(Error);
          expect((error as Error).message).toContain('Rate limit');
        }
      });
    });

    it('should handle 403 query limit exceeded error', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 403
      });

      const { result } = renderHook(() => useRunQuery(), { wrapper });

      await act(async () => {
        try {
          await result.current.mutateAsync(1);
        } catch (error) {
          expect(error).toBeInstanceOf(Error);
          expect((error as Error).message).toContain('Query limit');
        }
      });
    });

    it('should provide isLoading state', async () => {
      const { result } = renderHook(() => useRunQuery(), { wrapper });

      expect(result.current.isLoading).toBe(false);
    });
  });

  describe('API-KEYS: Query Keys', () => {
    it('should have correct query key structure', () => {
      expect(projectKeys.all).toEqual(['ai-visibility', 'projects']);
    });

    it('should generate list query key', () => {
      expect(projectKeys.lists()).toEqual(['ai-visibility', 'projects', 'list']);
    });

    it('should generate list with params query key', () => {
      const params = { page: 1, sort: 'created_at' };
      expect(projectKeys.list(params)).toEqual(['ai-visibility', 'projects', 'list', params]);
    });
  });
});
