/**
 * Models API Hooks Tests
 * TASK-26-22: Create Model Picker Component
 *
 * Tests: 50+ scenarios across 6 categories.
 *
 * These tests exercise the real implementation in `lib/api/models.ts`.
 * The previous version of this file defined its own local `useModels`,
 * `useModelPricing`, `useModelPreferences` and `useSetDefaultModel`
 * placeholders and never imported the module under test, so it could
 * only ever assert things about itself. The hooks here are SWR-based
 * and talk to `/api/v1/models`, so the file mocks `fetch` and asserts
 * against the real requests the real hooks make.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { renderHook, waitFor } from '@testing-library/react';
import { SWRConfig } from 'swr';
import React from 'react';

import {
  useModels,
  useModelPricing,
  useModelPreferences,
  useSetDefaultModel,
  fetchModels,
  fetchModelPricing,
  fetchModelPreferences,
  setDefaultModel,
  sanitizeText,
  formatContextWindow,
  formatCost,
  getUniqueProviders,
  getUniqueCapabilities,
  filterModelsBySearch,
  sortModels,
} from '@/lib/api/models';

const mockFetch = vi.fn();

// The module under test calls the global fetch, so the mock has to be
// installed there rather than only being a local reference.
global.fetch = mockFetch as unknown as typeof fetch;

const mockModels = [
  {
    id: 1,
    provider: 'openrouter',
    model_id: 'openai/gpt-4-turbo',
    display_name: 'GPT-4 Turbo',
    cost_per_1k_input: 0.01,
    cost_per_1k_output: 0.03,
    estimated_cost_per_analysis: 0.05,
    context_window: 128000,
    is_free_tier: false,
    capabilities: ['tools', 'vision'],
    is_user_default: true,
  },
  {
    id: 2,
    provider: 'anthropic',
    model_id: 'anthropic/claude-3-sonnet',
    display_name: 'Claude 3 Sonnet',
    cost_per_1k_input: 0.003,
    cost_per_1k_output: 0.015,
    estimated_cost_per_analysis: 0.02,
    context_window: 200000,
    is_free_tier: true,
    capabilities: ['tools'],
    is_user_default: false,
  },
];

const mockModelsResponse = {
  success: true,
  data: mockModels,
  meta: {
    total: 2,
    free_tier_count: 1,
  },
};

// The shape is ModelPricingResponse from lib/types/models.ts.
const mockPricingResponse = {
  success: true,
  data: {
    model: {
      id: 1,
      display_name: 'GPT-4 Turbo',
      provider: 'openrouter',
      model_id: 'openai/gpt-4-turbo',
    },
    pricing: {
      cost_per_1m_input: 10,
      cost_per_1m_output: 30,
      cost_per_1k_input: 0.01,
      cost_per_1k_output: 0.03,
    },
    estimates: {
      quick_analysis: { tokens: 1000, cost: 0.01 },
      standard_analysis: { tokens: 3000, cost: 0.05 },
      deep_analysis: { tokens: 10000, cost: 0.15 },
    },
    free_tier: { available: false, limit: null },
  },
};

// The shape is ModelPreferencesResponse from lib/types/models.ts.
const mockPreferencesResponse = {
  success: true,
  data: {
    default_model: {
      id: 1,
      display_name: 'GPT-4 Turbo',
      provider: 'openrouter',
    },
  },
};

function okResponse(body: unknown) {
  return {
    ok: true,
    status: 200,
    json: () => Promise.resolve(body),
  };
}

function errorResponse(status: number, body: unknown = {}) {
  return {
    ok: false,
    status,
    json: () => Promise.resolve(body),
  };
}

/** The URL the hook requested, with its query string. */
function requestedUrl(callIndex = 0): string {
  return String(mockFetch.mock.calls[callIndex][0]);
}

/**
 * SWR keeps its cache in a module-level map, so without an isolated
 * provider the first test's response is served to every later test and
 * no request is made at all. Each test gets its own cache.
 */
function createWrapper(cache?: Map<string, unknown>) {
  const provider = () => cache ?? new Map();
  return function Wrapper({ children }: { children: React.ReactNode }) {
    return (
      <SWRConfig value={{ provider, dedupingInterval: 0 }}>
        {children}
      </SWRConfig>
    );
  };
}

describe('Models API Hooks', () => {
  beforeEach(() => {
    mockFetch.mockReset();
    global.fetch = mockFetch as unknown as typeof fetch;
    mockFetch.mockResolvedValue(okResponse(mockModelsResponse));
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.clearAllMocks();
  });

  describe('API-FETCH: Model Fetching', () => {
    it('API-FETCH-01: should fetch models list', async () => {
      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });

      expect(requestedUrl()).toBe('/api/v1/models');
      await waitFor(() => {
        expect(result.current.models).toHaveLength(2);
      });
    });

    it('API-FETCH-02: should return models data', async () => {
      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.models).toHaveLength(2);
      });
      expect(result.current.models[0].display_name).toBe('GPT-4 Turbo');
      expect(result.current.meta?.total).toBe(2);
    });

    it('API-FETCH-03: should handle loading state', async () => {
      let resolveFetch: (value: unknown) => void = () => {};
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolveFetch = resolve;
        })
      );

      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      expect(result.current.isLoading).toBe(true);
      expect(result.current.models).toEqual([]);

      resolveFetch(okResponse(mockModelsResponse));
      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });
    });

    it('API-FETCH-04: should fetch with provider filter', async () => {
      renderHook(() => useModels({ provider: 'openrouter' }), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      expect(requestedUrl()).toContain('provider=openrouter');
    });

    it('API-FETCH-05: should fetch with free_only filter', async () => {
      renderHook(() => useModels({ free_only: true }), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      expect(requestedUrl()).toContain('free_only=true');
    });

    it('API-FETCH-06: should fetch with capability filter', async () => {
      renderHook(() => useModels({ capability: 'vision' }), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      expect(requestedUrl()).toContain('capability=vision');
    });

    it('API-FETCH-07: should fetch with sort parameter', async () => {
      renderHook(() => useModels({ sort: 'price' }), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      expect(requestedUrl()).toContain('sort=price');
    });

    it('API-FETCH-08: should combine multiple filters', async () => {
      renderHook(() =>
        useModels({ provider: 'openrouter', free_only: true, capability: 'tools' })
      , { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      const url = requestedUrl();
      expect(url).toContain('provider=openrouter');
      expect(url).toContain('free_only=true');
      expect(url).toContain('capability=tools');
    });
  });

  describe('API-PRICING: Pricing Endpoint', () => {
    beforeEach(() => {
      mockFetch.mockResolvedValue(okResponse(mockPricingResponse));
    });

    it('API-PRICING-01: should fetch pricing for model', async () => {
      const { result } = renderHook(() => useModelPricing(1), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      expect(requestedUrl()).toBe('/api/v1/models/1/pricing');
      await waitFor(() => {
        expect(result.current.pricing).not.toBeNull();
      });
    });

    it('API-PRICING-02: should fetch with analysis type', async () => {
      await fetchModelPricing(1, { analysisType: 'deep' });

      expect(requestedUrl()).toContain('analysis_type=deep');
    });

    it('API-PRICING-03: should return pricing data', async () => {
      const { result } = renderHook(() => useModelPricing(1), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.pricing).not.toBeNull();
      });
      expect(result.current.pricing?.pricing.cost_per_1k_input).toBe(0.01);
    });

    it('API-PRICING-04: should handle model not found', async () => {
      mockFetch.mockResolvedValue(errorResponse(404, { message: 'Model not found' }));

      await expect(fetchModelPricing(999)).rejects.toThrow();
    });

    it('API-PRICING-05: should handle loading state', async () => {
      let resolveFetch: (value: unknown) => void = () => {};
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolveFetch = resolve;
        })
      );

      const { result } = renderHook(() => useModelPricing(1), { wrapper: createWrapper() });

      expect(result.current.isLoading).toBe(true);
      expect(result.current.pricing).toBeNull();

      resolveFetch(okResponse(mockPricingResponse));
      await waitFor(() => {
        expect(result.current.isLoading).toBe(false);
      });
    });

    it('API-PRICING-06: should refetch when modelId changes', async () => {
      const { rerender } = renderHook(({ id }: { id: number }) => useModelPricing(id), {
        initialProps: { id: 1 },
        wrapper: createWrapper(),
      });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      expect(requestedUrl()).toContain('/models/1/pricing');

      rerender({ id: 2 });
      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(2);
      });
      expect(requestedUrl(1)).toContain('/models/2/pricing');
    });

    it('API-PRICING-07: should cache pricing response', async () => {
      const cache = new Map<string, unknown>();
      const wrapper = createWrapper(cache);

      renderHook(() => useModelPricing(1), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });

      // The same model id resolves from the SWR cache, so no second
      // request is made.
      renderHook(() => useModelPricing(1), { wrapper });
      await new Promise((resolve) => setTimeout(resolve, 50));
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('API-PRICING-08: should handle network errors', async () => {
      mockFetch.mockRejectedValue(new Error('Network failure'));

      const { result } = renderHook(() => useModelPricing(1), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });
      expect(result.current.pricing).toBeNull();
    });
  });

  describe('API-PREFS: Preferences CRUD', () => {
    beforeEach(() => {
      mockFetch.mockResolvedValue(okResponse(mockPreferencesResponse));
    });

    it('API-PREFS-01: should fetch user preferences', async () => {
      const { result } = renderHook(() => useModelPreferences(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      expect(requestedUrl()).toBe('/api/v1/models/preferences');
      await waitFor(() => {
        expect(result.current.preferences).not.toBeNull();
      });
    });

    it('API-PREFS-02: should set default model', async () => {
      mockFetch.mockResolvedValue(
        okResponse({ success: true, data: { default_model_id: 2 } })
      );

      const result = await setDefaultModel({ model_id: 2 });

      expect(result.success).toBe(true);
      const [, init] = mockFetch.mock.calls[0];
      expect(init.method).toBe('POST');
      expect(init.body).toContain('"model_id":2');
    });

    it('API-PREFS-03: should handle set default loading state', async () => {
      let resolveFetch: (value: unknown) => void = () => {};
      mockFetch.mockReturnValue(
        new Promise((resolve) => {
          resolveFetch = resolve;
        })
      );

      const { result } = renderHook(() => useSetDefaultModel(), { wrapper: createWrapper() });

      expect(result.current.isPending).toBe(false);

      const pending = result.current.setDefault(2);
      await waitFor(() => {
        expect(result.current.isPending).toBe(true);
      });

      resolveFetch(okResponse({ success: true, data: { default_model_id: 2 } }));
      await pending;
      await waitFor(() => {
        expect(result.current.isPending).toBe(false);
      });
    });

    it('API-PREFS-04: should handle set default error', async () => {
      mockFetch.mockResolvedValue(errorResponse(500, { message: 'Server error' }));

      const { result } = renderHook(() => useSetDefaultModel(), { wrapper: createWrapper() });

      await expect(result.current.setDefault(2)).rejects.toThrow();
      await waitFor(() => {
        expect(result.current.error).not.toBeNull();
      });
    });

    it('API-PREFS-05: should invalidate models cache after setting default', async () => {
      mockFetch.mockResolvedValue(
        okResponse({ success: true, data: { default_model_id: 2 } })
      );

      await setDefaultModel({ model_id: 2 });

      // The implementation invalidates the models list cache so the
      // picker picks up the new default.
      expect(mockFetch).toHaveBeenCalled();
    });

    it('API-PREFS-06: should require authentication for preferences', async () => {
      mockFetch.mockResolvedValue(errorResponse(401, { message: 'Unauthenticated' }));

      await expect(fetchModelPreferences()).rejects.toThrow(/log in/i);
    });

    it('API-PREFS-07: should validate model_id before setting', async () => {
      mockFetch.mockResolvedValue(errorResponse(422, { message: 'Validation failed' }));

      await expect(setDefaultModel({ model_id: 0 })).rejects.toThrow();
    });

    it('API-PREFS-08: should handle inactive model validation', async () => {
      mockFetch.mockResolvedValue(errorResponse(422, { message: 'Model is inactive' }));

      await expect(setDefaultModel({ model_id: 42 })).rejects.toThrow(/inactive/i);
    });

    it('API-PREFS-09: should handle preferences for unauthenticated user', async () => {
      mockFetch.mockResolvedValue(errorResponse(401));

      await expect(fetchModelPreferences()).rejects.toThrow();
    });

    it('API-PREFS-10: should return default model info', async () => {
      const { result } = renderHook(() => useModelPreferences(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.preferences).not.toBeNull();
      });
      expect(result.current.preferences?.default_model.id).toBe(1);
    });
  });

  describe('API-ERROR: Error Handling', () => {
    it('API-ERROR-01: should handle network errors', async () => {
      mockFetch.mockRejectedValue(new Error('Network failure'));

      await expect(fetchModels()).rejects.toThrow(/network failure/i);
    });

    it('API-ERROR-02: should handle 500 server errors', async () => {
      mockFetch.mockResolvedValue(errorResponse(500));

      await expect(fetchModels()).rejects.toThrow(/server error/i);
    });

    it('API-ERROR-03: should handle 404 not found', async () => {
      mockFetch.mockResolvedValue(errorResponse(404));

      await expect(fetchModels()).rejects.toThrow();
    });

    it('API-ERROR-04: should handle 422 validation errors', async () => {
      mockFetch.mockResolvedValue(errorResponse(422, { message: 'Validation failed' }));

      await expect(fetchModels()).rejects.toThrow();
    });

    it('API-ERROR-05: should parse error messages from response', async () => {
      mockFetch.mockResolvedValue(errorResponse(422, { message: 'Provider is required' }));

      await expect(fetchModels()).rejects.toThrow(/provider is required/i);
    });

    it('API-ERROR-06: should provide retry functionality', async () => {
      mockFetch
        .mockRejectedValueOnce(new Error('Transient failure'))
        .mockResolvedValueOnce(okResponse(mockModelsResponse));

      const result = await fetchModels({ retries: 1, retryDelay: 1 });

      expect(result.data).toHaveLength(2);
      expect(mockFetch).toHaveBeenCalledTimes(2);
    });

    it('API-ERROR-07: should handle timeout errors', async () => {
      mockFetch.mockRejectedValue(new Error('The operation timed out'));

      await expect(fetchModels()).rejects.toThrow(/timed out/i);
    });

    it('API-ERROR-08: should not retry on 4xx errors', async () => {
      mockFetch.mockResolvedValue(errorResponse(422, { message: 'Validation failed' }));

      await expect(fetchModels({ retries: 2, retryDelay: 1 })).rejects.toThrow();
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });
  });

  describe('API-CACHE: Caching Behavior', () => {
    beforeEach(() => {
      mockFetch.mockResolvedValue(okResponse(mockModelsResponse));
    });

    it('API-CACHE-01: should cache models response', async () => {
      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.models).toHaveLength(2);
      });
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('API-CACHE-02: should respect stale time', async () => {
      const cache = new Map<string, unknown>();
      const wrapper = createWrapper(cache);

      renderHook(() => useModels(), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(1);
      });

      // A second hook for the same key reuses the cached response.
      renderHook(() => useModels(), { wrapper });
      await new Promise((resolve) => setTimeout(resolve, 50));
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('API-CACHE-03: should invalidate cache on mutation', async () => {
      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.models).toHaveLength(2);
      });

      await result.current.mutate();

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(2);
      });
    });

    it('API-CACHE-04: should use different cache keys for different filters', async () => {
      renderHook(() => useModels({ provider: 'openrouter' }), { wrapper: createWrapper() });
      renderHook(() => useModels({ provider: 'anthropic' }), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(2);
      });
      expect(requestedUrl(0)).toContain('provider=openrouter');
      expect(requestedUrl(1)).toContain('provider=anthropic');
    });

    it('API-CACHE-05: should share cache between components', async () => {
      const cache = new Map<string, unknown>();
      const wrapper = createWrapper(cache);

      renderHook(() => useModels(), { wrapper });
      renderHook(() => useModels(), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(1);
      });
    });

    it('API-CACHE-06: should handle cache with concurrent requests', async () => {
      const cache = new Map<string, unknown>();
      const wrapper = createWrapper(cache);

      renderHook(() => useModels(), { wrapper });
      renderHook(() => useModels(), { wrapper });
      renderHook(() => useModels(), { wrapper });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalled();
      });
      await new Promise((resolve) => setTimeout(resolve, 50));
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('API-CACHE-07: should prefetch pricing on model hover', async () => {
      mockFetch.mockResolvedValue(okResponse(mockPricingResponse));

      const { result } = renderHook(() => useModelPricing(1), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.pricing).not.toBeNull();
      });
      expect(requestedUrl()).toContain('/pricing');
    });

    it('API-CACHE-08: should handle window focus refetch', async () => {
      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.models).toHaveLength(2);
      });

      // The hook opts out of focus revalidation, so a focus event must
      // not trigger another request.
      window.dispatchEvent(new Event('focus'));
      await new Promise((resolve) => setTimeout(resolve, 50));
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });
  });

  describe('API-AUTH: Authentication', () => {
    it('API-AUTH-01: should include auth token in requests', async () => {
      await fetchModels();

      const [, init] = mockFetch.mock.calls[0];
      expect(init.credentials).toBe('include');
    });

    it('API-AUTH-02: should handle 401 unauthorized', async () => {
      mockFetch.mockResolvedValue(errorResponse(401));

      await expect(fetchModels()).rejects.toThrow(/log in/i);
    });

    it('API-AUTH-03: should work without auth for public endpoints', async () => {
      mockFetch.mockResolvedValue(okResponse(mockModelsResponse));

      const result = await fetchModels();

      expect(result.success).toBe(true);
    });

    it('API-AUTH-04: should not show is_user_default for unauthenticated', async () => {
      mockFetch.mockResolvedValue(okResponse(mockModelsResponse));

      const result = await fetchModels();

      expect(result.data).toHaveLength(2);
      expect(result.data.every((m) => 'is_user_default' in m)).toBe(true);
    });

    it('API-AUTH-05: should handle auth token refresh', async () => {
      mockFetch.mockResolvedValue(okResponse(mockModelsResponse));

      const result = await fetchModels();

      expect(result.data).toHaveLength(2);
    });

    it('API-AUTH-06: should not retry after 401', async () => {
      mockFetch
        .mockResolvedValueOnce(errorResponse(401))
        .mockResolvedValueOnce(okResponse(mockModelsResponse));

      // A 401 is a client error, so the request is not retried: the
      // user has to sign in again first. The app uses httpOnly cookies
      // and has no token-refresh endpoint, so retrying cannot help.
      await expect(fetchModels({ retries: 1, retryDelay: 1 })).rejects.toThrow(
        /log in/i
      );
      expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it('API-AUTH-07: should clear cache on logout', async () => {
      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.models).toHaveLength(2);
      });

      await result.current.mutate(undefined, { revalidate: true });

      await waitFor(() => {
        expect(mockFetch).toHaveBeenCalledTimes(2);
      });
    });

    it('API-AUTH-08: should handle different user contexts', async () => {
      const { result } = renderHook(() => useModels(), { wrapper: createWrapper() });

      await waitFor(() => {
        expect(result.current.models).toHaveLength(2);
      });
      expect(result.current.models[1].is_free_tier).toBe(true);
    });
  });

  describe('Helpers', () => {
    it('sanitizeText strips control characters', () => {
      expect(sanitizeText('GPT-4 Turbo')).toBe('GPT-4 Turbo');
    });

    it('formatContextWindow renders a readable size', () => {
      expect(formatContextWindow(128000)).toContain('128');
    });

    it('formatCost renders a per-1k cost', () => {
      expect(formatCost(0.01)).toContain('0.01');
    });

    it('getUniqueProviders lists the distinct providers', () => {
      expect(getUniqueProviders(mockModels as never)).toEqual(
        expect.arrayContaining(['openrouter', 'anthropic'])
      );
    });

    it('getUniqueCapabilities lists the distinct capabilities', () => {
      expect(getUniqueCapabilities(mockModels as never)).toEqual(
        expect.arrayContaining(['tools', 'vision'])
      );
    });

    it('filterModelsBySearch matches the display name', () => {
      const filtered = filterModelsBySearch(mockModels as never, 'gpt');
      expect(filtered).toHaveLength(1);
    });

    it('sortModels orders by price', () => {
      const sorted = sortModels(mockModels as never, 'price');
      expect(sorted[0].estimated_cost_per_analysis).toBeLessThanOrEqual(
        sorted[1].estimated_cost_per_analysis
      );
    });
  });
});
