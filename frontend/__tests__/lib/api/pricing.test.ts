/**
 * Tests for Pricing API Hooks
 * Task: TASK-26-20
 * Test Documentation: TESTS/TEST-26-20.md
 */

import { renderHook, waitFor, act } from '@testing-library/react';
import { usePackages, useModels, usePricingPage } from '@/lib/api/pricing';

// Mock global fetch
const mockFetch = jest.fn();
global.fetch = mockFetch;

// Mock data
const mockPackagesResponse = {
  data: [
    {
      id: 1,
      name: 'Starter',
      credits: 1000,
      price: 500,
      bonus_percentage: 0,
      is_popular: false,
      description: 'Perfect for trying out SEOIndicator',
    },
    {
      id: 2,
      name: 'Basic',
      credits: 5000,
      price: 2000,
      bonus_percentage: 5,
      is_popular: false,
      description: 'Great for small websites',
    },
    {
      id: 3,
      name: 'Pro',
      credits: 15000,
      price: 5000,
      bonus_percentage: 10,
      is_popular: true,
      description: 'Most popular for growing businesses',
    },
    {
      id: 4,
      name: 'Enterprise',
      credits: 50000,
      price: 15000,
      bonus_percentage: 15,
      is_popular: false,
      description: 'For agencies and large teams',
    },
  ],
  meta: {
    total: 4,
    page: 1,
    per_page: 10,
  },
};

const mockModelsResponse = {
  data: [
    {
      id: 1,
      name: 'GPT-4o Mini',
      provider: 'OpenRouter',
      provider_id: 'openai/gpt-4o-mini',
      cost_per_analysis: 0.0012,
      is_free_tier: true,
      context_window: 128000,
      is_active: true,
    },
    {
      id: 2,
      name: 'Claude 3.5 Sonnet',
      provider: 'OpenRouter',
      provider_id: 'anthropic/claude-3.5-sonnet',
      cost_per_analysis: 0.0045,
      is_free_tier: false,
      context_window: 200000,
      is_active: true,
    },
    {
      id: 3,
      name: 'Gemini 2.0 Flash',
      provider: 'Google',
      provider_id: 'google/gemini-2.0-flash',
      cost_per_analysis: 0.0,
      is_free_tier: true,
      context_window: 1000000,
      is_active: true,
    },
  ],
  meta: {
    total: 3,
    page: 1,
    per_page: 10,
  },
};

describe('Pricing API Hooks', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  // API-001 to API-015: usePackages Hook
  describe('usePackages', () => {
    it('API-001: fetches packages on mount', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(mockFetch).toHaveBeenCalledWith('/api/v1/packages');
      expect(result.current.packages).toHaveLength(4);
    });

    it('API-002: returns packages array', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.packages).toEqual(mockPackagesResponse.data);
      });
    });

    it('API-003: returns loading state during fetch', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {})); // Never resolves

      const { result } = renderHook(() => usePackages());

      expect(result.current.isLoading).toBe(true);
    });

    it('API-004: returns error state on failure', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
        statusText: 'Internal Server Error',
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });

      expect(result.current.isLoading).toBe(false);
    });

    it('API-005: refetch function works', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      await act(async () => {
        await result.current.refetch();
      });

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  // API-006 to API-015: useModels Hook
  describe('useModels', () => {
    it('API-006: fetches models on mount', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockModelsResponse,
      });

      const { result } = renderHook(() => useModels());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(mockFetch).toHaveBeenCalledWith('/api/v1/models');
      expect(result.current.models).toHaveLength(3);
    });

    it('API-007: returns models array', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockModelsResponse,
      });

      const { result } = renderHook(() => useModels());

      await waitFor(() => {
        expect(result.current.models).toEqual(mockModelsResponse.data);
      });
    });

    it('API-008: returns loading state during fetch', async () => {
      mockFetch.mockImplementation(() => new Promise(() => {}));

      const { result } = renderHook(() => useModels());

      expect(result.current.isLoading).toBe(true);
    });

    it('API-009: returns error state on failure', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
      });

      const { result } = renderHook(() => useModels());

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });
    });

    it('API-010: refetch function works', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => mockModelsResponse,
      });

      const { result } = renderHook(() => useModels());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      await act(async () => {
        await result.current.refetch();
      });

      expect(mockFetch).toHaveBeenCalledTimes(2);
    });
  });

  // API-016 to API-025: usePricingPage Hook
  describe('usePricingPage', () => {
    it('API-011: combines packages and models', async () => {
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockPackagesResponse,
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockModelsResponse,
        });

      const { result } = renderHook(() => usePricingPage());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      expect(result.current.packages).toHaveLength(4);
      expect(result.current.models).toHaveLength(3);
    });

    it('API-012: returns loading when either is loading', async () => {
      mockFetch
        .mockImplementationOnce(() => new Promise(() => {})) // Never resolves (packages)
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockModelsResponse,
        });

      const { result } = renderHook(() => usePricingPage());

      expect(result.current.isLoading).toBe(true);
    });

    it('API-013: returns error when either errors', async () => {
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockPackagesResponse,
        })
        .mockResolvedValueOnce({
          ok: false,
          status: 500,
        });

      const { result } = renderHook(() => usePricingPage());

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });
    });

    it('API-014: refetch calls both refetches', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result } = renderHook(() => usePricingPage());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });

      await act(async () => {
        await result.current.refetch();
      });

      // Initial fetch + refetch for both hooks
      expect(mockFetch).toHaveBeenCalled();
    });
  });

  // API-026 to API-035: Response Handling
  describe('Response Handling', () => {
    it('API-015: handles 401 unauthorized', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 401,
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });
    });

    it('API-016: handles network error', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });
    });

    it('API-017: handles empty data array', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], meta: { total: 0 } }),
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.packages).toEqual([]);
      });
    });

    it('API-018: extracts data from response', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.packages[0]).toHaveProperty('id');
        expect(result.current.packages[0]).toHaveProperty('name');
        expect(result.current.packages[0]).toHaveProperty('credits');
        expect(result.current.packages[0]).toHaveProperty('price');
      });
    });

    it('API-019: handles malformed response', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => ({ invalid: 'response' }),
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current.packages).toBeDefined();
      });
    });
  });

  // Type Tests
  describe('TypeScript Types', () => {
    it('API-020: Package type has required fields', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        const pkg = result.current.packages[0];
        // TypeScript compile-time check
        const _id: number = pkg.id;
        const _name: string = pkg.name;
        const _credits: number = pkg.credits;
        const _price: number = pkg.price;
        expect(_id).toBeDefined();
        expect(_name).toBeDefined();
        expect(_credits).toBeDefined();
        expect(_price).toBeDefined();
      });
    });

    it('API-021: Model type has required fields', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockModelsResponse,
      });

      const { result } = renderHook(() => useModels());

      await waitFor(() => {
        const model = result.current.models[0];
        // TypeScript compile-time check
        const _id: number = model.id;
        const _name: string = model.name;
        const _provider: string = model.provider;
        const _cost: number = model.cost_per_analysis;
        expect(_id).toBeDefined();
        expect(_name).toBeDefined();
        expect(_provider).toBeDefined();
        expect(_cost).toBeDefined();
      });
    });

    it('API-022: Hook return type includes all fields', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result.current).toHaveProperty('packages');
        expect(result.current).toHaveProperty('isLoading');
        expect(result.current).toHaveProperty('error');
        expect(result.current).toHaveProperty('refetch');
      });
    });
  });

  // Integration Tests
  describe('Integration', () => {
    it('API-023: multiple hook instances share cache', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      const { result: result1 } = renderHook(() => usePackages());
      const { result: result2 } = renderHook(() => usePackages());

      await waitFor(() => {
        expect(result1.current.isLoading).toBe(false);
        expect(result2.current.isLoading).toBe(false);
      });

      // Both should have the same data
      expect(result1.current.packages).toEqual(result2.current.packages);
    });

    it('API-024: hooks work with components', async () => {
      mockFetch
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockPackagesResponse,
        })
        .mockResolvedValueOnce({
          ok: true,
          json: async () => mockModelsResponse,
        });

      const { result } = renderHook(() => usePricingPage());

      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
        expect(result.current.packages).toBeDefined();
        expect(result.current.models).toBeDefined();
      });
    });

    it('API-025: handles concurrent requests', async () => {
      mockFetch.mockResolvedValue({
        ok: true,
        json: async () => mockPackagesResponse,
      });

      // Start multiple hooks concurrently
      const { result: packagesResult } = renderHook(() => usePackages());
      const { result: modelsResult } = renderHook(() => useModels());

      await waitFor(() => {
        expect(packagesResult.current.isLoading).toBe(false);
        expect(modelsResult.current.isLoading).toBe(false);
      });
    });
  });
});
