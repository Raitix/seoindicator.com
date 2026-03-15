/**
 * Models API functions for SEOIndicator
 *
 * Fetches AI models, pricing, and manages user preferences.
 * Uses SWR for caching and real-time updates.
 *
 * @see SPEC-026
 * @see TASK-26-22
 */

import useSWR, { mutate as globalMutate, type MutatorCallback } from 'swr';
import type {
  AIModel,
  ModelsListResponse,
  ModelPricingResponse,
  ModelPreferencesResponse,
  ModelsQueryParams,
  SetDefaultModelRequest,
  ModelsError,
} from '@/lib/types/models';

// ==================== Constants ====================

const API_BASE = '/api/v1';
const MODELS_ENDPOINT = `${API_BASE}/models`;

// ==================== Error Class ====================

class ModelsAPIError extends Error implements ModelsError {
  public status?: number;
  public code?: string;

  constructor(message: string, status?: number, code?: string) {
    super(message);
    this.name = 'ModelsAPIError';
    this.status = status;
    this.code = code;
  }
}

// ==================== Helper Functions ====================

/**
 * Build query string from parameters
 */
function buildQueryString(params: ModelsQueryParams): string {
  const searchParams = new URLSearchParams();

  if (params.provider) searchParams.set('provider', params.provider);
  if (params.free_only) searchParams.set('free_only', 'true');
  if (params.capability) searchParams.set('capability', params.capability);
  if (params.search) searchParams.set('search', params.search);
  if (params.sort) searchParams.set('sort', params.sort);

  const queryString = searchParams.toString();
  return queryString ? `?${queryString}` : '';
}

/**
 * Sleep utility for retry delays
 */
function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

// ==================== fetchModels ====================

export interface FetchModelsOptions extends ModelsQueryParams {
  retries?: number;
  retryDelay?: number;
}

/**
 * Fetch available AI models from the API
 */
export async function fetchModels(
  options: FetchModelsOptions = {}
): Promise<ModelsListResponse> {
  const { retries = 0, retryDelay = 1000, ...params } = options;

  let lastError: Error | null = null;
  let attempt = 0;

  while (attempt <= retries) {
    try {
      const queryString = buildQueryString(params);
      const url = `${MODELS_ENDPOINT}${queryString}`;

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
      });

      if (!response.ok) {
        if (response.status === 401) {
          throw new ModelsAPIError('Unauthorized. Please log in.', 401, 'UNAUTHORIZED');
        }

        if (response.status === 429) {
          throw new ModelsAPIError('Rate limit exceeded. Please try again later.', 429, 'RATE_LIMITED');
        }

        if (response.status >= 500) {
          throw new ModelsAPIError('Server error. Please try again later.', response.status, 'SERVER_ERROR');
        }

        try {
          const errorData = await response.json();
          throw new ModelsAPIError(
            errorData?.message || `Request failed with status ${response.status}`,
            response.status,
            errorData?.code
          );
        } catch {
          throw new ModelsAPIError(`Request failed with status ${response.status}`, response.status);
        }
      }

      const data: ModelsListResponse = await response.json();
      return data;
    } catch (error) {
      lastError = error as Error;

      if (error instanceof ModelsAPIError && error.status && error.status < 500 && error.status !== 429) {
        throw error;
      }

      if (attempt < retries) {
        await sleep(retryDelay * Math.pow(2, attempt));
        attempt++;
        continue;
      }

      if (error instanceof TypeError && error.message.includes('fetch')) {
        throw new ModelsAPIError('Network error. Please check your connection.', 0, 'NETWORK_ERROR');
      }

      throw error;
    }

    attempt++;
  }

  throw lastError || new ModelsAPIError('Failed to fetch models');
}

// ==================== useModels Hook ====================

export interface UseModelsReturn {
  models: AIModel[];
  meta: { total: number; free_tier_count: number } | null;
  isLoading: boolean;
  isValidating: boolean;
  error: Error | null;
  mutate: (
    data?: ModelsListResponse | Promise<ModelsListResponse> | MutatorCallback<ModelsListResponse>,
    options?: { revalidate?: boolean }
  ) => Promise<ModelsListResponse | undefined>;
  refetch: () => Promise<void>;
}

/**
 * Hook to fetch and cache AI models list
 */
export function useModels(params: ModelsQueryParams = {}): UseModelsReturn {
  const queryString = buildQueryString(params);
  const key = `${MODELS_ENDPOINT}${queryString}`;

  const { data, isLoading, isValidating, error, mutate } = useSWR<ModelsListResponse, Error>(
    key,
    () => fetchModels(params),
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: true,
      shouldRetryOnError: false,
      dedupingInterval: 10000, // Dedupe requests within 10 seconds
      errorRetryCount: 0,
    }
  );

  const refetch = async () => {
    await mutate();
  };

  return {
    models: data?.data || [],
    meta: data?.meta || null,
    isLoading,
    isValidating,
    error: error || null,
    mutate,
    refetch,
  };
}

// ==================== fetchModelPricing ====================

export interface FetchPricingOptions {
  analysisType?: 'quick' | 'standard' | 'deep';
  retries?: number;
  retryDelay?: number;
}

/**
 * Fetch pricing information for a specific model
 */
export async function fetchModelPricing(
  modelId: number,
  options: FetchPricingOptions = {}
): Promise<ModelPricingResponse> {
  const { analysisType, retries = 0, retryDelay = 1000 } = options;

  let lastError: Error | null = null;
  let attempt = 0;

  while (attempt <= retries) {
    try {
      const searchParams = new URLSearchParams();
      if (analysisType) searchParams.set('analysis_type', analysisType);

      const queryString = searchParams.toString();
      const url = `${MODELS_ENDPOINT}/${modelId}/pricing${queryString ? `?${queryString}` : ''}`;

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
      });

      if (!response.ok) {
        if (response.status === 404) {
          throw new ModelsAPIError('Model not found', 404, 'NOT_FOUND');
        }

        if (response.status === 401) {
          throw new ModelsAPIError('Unauthorized. Please log in.', 401, 'UNAUTHORIZED');
        }

        throw new ModelsAPIError(`Request failed with status ${response.status}`, response.status);
      }

      const data: ModelPricingResponse = await response.json();
      return data;
    } catch (error) {
      lastError = error as Error;

      if (error instanceof ModelsAPIError && error.status && error.status < 500) {
        throw error;
      }

      if (attempt < retries) {
        await sleep(retryDelay * Math.pow(2, attempt));
        attempt++;
        continue;
      }

      throw error;
    }

    attempt++;
  }

  throw lastError || new ModelsAPIError('Failed to fetch model pricing');
}

// ==================== useModelPricing Hook ====================

export interface UseModelPricingReturn {
  pricing: ModelPricingResponse['data'] | null;
  isLoading: boolean;
  isValidating: boolean;
  error: Error | null;
  mutate: (
    data?: ModelPricingResponse | Promise<ModelPricingResponse> | MutatorCallback<ModelPricingResponse>,
    options?: { revalidate?: boolean }
  ) => Promise<ModelPricingResponse | undefined>;
  refetch: () => Promise<void>;
}

/**
 * Hook to fetch and cache model pricing
 */
export function useModelPricing(
  modelId: number | null | undefined,
  analysisType?: 'quick' | 'standard' | 'deep'
): UseModelPricingReturn {
  const key = modelId ? `${MODELS_ENDPOINT}/${modelId}/pricing` : null;

  const { data, isLoading, isValidating, error, mutate } = useSWR<ModelPricingResponse, Error>(
    key,
    () => fetchModelPricing(modelId!, { analysisType }),
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: true,
      shouldRetryOnError: false,
      dedupingInterval: 30000, // Cache pricing for 30 seconds
      errorRetryCount: 0,
    }
  );

  const refetch = async () => {
    await mutate();
  };

  return {
    pricing: data?.data || null,
    isLoading,
    isValidating,
    error: error || null,
    mutate,
    refetch,
  };
}

// ==================== fetchModelPreferences ====================

/**
 * Fetch user's model preferences
 */
export async function fetchModelPreferences(): Promise<ModelPreferencesResponse> {
  const url = `${MODELS_ENDPOINT}/preferences`;

  const response = await fetch(url, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include',
  });

  if (!response.ok) {
    if (response.status === 401) {
      throw new ModelsAPIError('Unauthorized. Please log in.', 401, 'UNAUTHORIZED');
    }

    throw new ModelsAPIError(`Request failed with status ${response.status}`, response.status);
  }

  const data: ModelPreferencesResponse = await response.json();
  return data;
}

// ==================== useModelPreferences Hook ====================

export interface UseModelPreferencesReturn {
  preferences: ModelPreferencesResponse['data'] | null;
  isLoading: boolean;
  isValidating: boolean;
  error: Error | null;
  mutate: (
    data?: ModelPreferencesResponse | Promise<ModelPreferencesResponse> | MutatorCallback<ModelPreferencesResponse>,
    options?: { revalidate?: boolean }
  ) => Promise<ModelPreferencesResponse | undefined>;
  refetch: () => Promise<void>;
}

/**
 * Hook to fetch and cache user model preferences
 */
export function useModelPreferences(): UseModelPreferencesReturn {
  const key = `${MODELS_ENDPOINT}/preferences`;

  const { data, isLoading, isValidating, error, mutate } = useSWR<ModelPreferencesResponse, Error>(
    key,
    () => fetchModelPreferences(),
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: true,
      shouldRetryOnError: false,
      dedupingInterval: 60000, // Cache preferences for 60 seconds
      errorRetryCount: 0,
    }
  );

  const refetch = async () => {
    await mutate();
  };

  return {
    preferences: data?.data || null,
    isLoading,
    isValidating,
    error: error || null,
    mutate,
    refetch,
  };
}

// ==================== setDefaultModel ====================

export interface SetDefaultModelResult {
  success: boolean;
  data: {
    default_model: {
      id: number;
      display_name: string;
      provider: string;
    };
  };
}

/**
 * Set user's default model
 */
export async function setDefaultModel(request: SetDefaultModelRequest): Promise<SetDefaultModelResult> {
  const url = `${MODELS_ENDPOINT}/preferences`;

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify(request),
  });

  if (!response.ok) {
    if (response.status === 401) {
      throw new ModelsAPIError('Unauthorized. Please log in.', 401, 'UNAUTHORIZED');
    }

    if (response.status === 422) {
      const errorData = await response.json();
      throw new ModelsAPIError(
        errorData?.message || 'Validation failed',
        422,
        'VALIDATION_ERROR'
      );
    }

    throw new ModelsAPIError(`Request failed with status ${response.status}`, response.status);
  }

  const data: SetDefaultModelResult = await response.json();

  // Invalidate models cache to update is_user_default flags
  await invalidateModelsCache();

  return data;
}

// ==================== useSetDefaultModel Hook ====================

import { useState, useCallback } from 'react';

export interface UseSetDefaultModelReturn {
  setDefault: (modelId: number) => Promise<SetDefaultModelResult>;
  isPending: boolean;
  error: Error | null;
  reset: () => void;
}

/**
 * Hook to set user's default model with loading state
 */
export function useSetDefaultModel(): UseSetDefaultModelReturn {
  const [isPending, setIsPending] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const setDefault = useCallback(async (modelId: number): Promise<SetDefaultModelResult> => {
    setIsPending(true);
    setError(null);

    try {
      const result = await setDefaultModel({ model_id: modelId });
      return result;
    } catch (err) {
      const error = err instanceof Error ? err : new Error('Failed to set default model');
      setError(error);
      throw error;
    } finally {
      setIsPending(false);
    }
  }, []);

  const reset = useCallback(() => {
    setError(null);
    setIsPending(false);
  }, []);

  return {
    setDefault,
    isPending,
    error,
    reset,
  };
}

// ==================== Cache Invalidation ====================

/**
 * Invalidate all models-related caches
 */
export async function invalidateModelsCache(): Promise<void> {
  // Invalidate models list
  await globalMutate(
    (key: string) => key.startsWith(MODELS_ENDPOINT) && !key.includes('/pricing') && !key.includes('/preferences'),
    undefined,
    { revalidate: true }
  );
}

/**
 * Invalidate preferences cache
 */
export async function invalidatePreferencesCache(): Promise<void> {
  await globalMutate(`${MODELS_ENDPOINT}/preferences`);
}

// ==================== Utility Functions ====================

/**
 * Format context window size in human-readable format
 */
export function formatContextWindow(size: number): string {
  if (size >= 1000000) {
    return `${(size / 1000000).toFixed(1)}M`;
  }
  if (size >= 1000) {
    return `${(size / 1000).toFixed(0)}K`;
  }
  return size.toString();
}

/**
 * Format cost as currency string
 */
export function formatCost(cost: number): string {
  if (cost === 0) {
    return 'Free';
  }
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 4,
  }).format(cost);
}

/**
 * Get unique providers from models list
 */
export function getUniqueProviders(models: AIModel[]): string[] {
  const providers = new Set(models.map((m) => m.provider));
  return Array.from(providers).sort();
}

/**
 * Get unique capabilities from models list
 */
export function getUniqueCapabilities(models: AIModel[]): string[] {
  const capabilities = new Set<string>();
  models.forEach((m) => {
    m.capabilities.forEach((cap) => capabilities.add(cap));
  });
  return Array.from(capabilities).sort();
}

/**
 * Filter models by search term
 */
export function filterModelsBySearch(models: AIModel[], search: string): AIModel[] {
  if (!search.trim()) return models;

  const searchLower = search.toLowerCase();
  return models.filter(
    (m) =>
      m.display_name.toLowerCase().includes(searchLower) ||
      m.provider.toLowerCase().includes(searchLower) ||
      m.model_id.toLowerCase().includes(searchLower)
  );
}

/**
 * Sort models by given criteria
 */
export function sortModels(models: AIModel[], sortBy?: 'name' | 'price' | 'context_window'): AIModel[] {
  const sorted = [...models];

  switch (sortBy) {
    case 'name':
      return sorted.sort((a, b) => a.display_name.localeCompare(b.display_name));
    case 'price':
      return sorted.sort((a, b) => a.estimated_cost_per_analysis - b.estimated_cost_per_analysis);
    case 'context_window':
      return sorted.sort((a, b) => b.context_window - a.context_window);
    default:
      // Default: free models first, then by price
      return sorted.sort((a, b) => {
        if (a.is_free_tier && !b.is_free_tier) return -1;
        if (!a.is_free_tier && b.is_free_tier) return 1;
        return a.estimated_cost_per_analysis - b.estimated_cost_per_analysis;
      });
  }
}
