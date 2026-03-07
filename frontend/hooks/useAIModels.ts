/**
 * useAIModels Hook - TASK-31-15
 *
 * React hook for fetching available AI models from the API.
 *
 * Features:
 * - Fetches models from /api/v1/ai/models
 * - Returns default model and user preference
 * - Handles loading and error states
 * - Uses SWR for caching and revalidation
 *
 * @see TASK-31-15
 * @see TEST-31-15
 */

'use client';

import useSWR from 'swr';
import type {
  AIModel,
  AIModelsResponse,
  UseAIModelsReturn,
} from '@/types/ai-models';

// API endpoint for fetching models
const API_ENDPOINT = '/api/v1/ai/models';

// SWR fetcher
const fetcher = async (url: string): Promise<AIModelsResponse> => {
  const response = await fetch(url);
  
  if (!response.ok) {
    throw new Error('Failed to fetch models');
  }
  
  return response.json();
};

/**
 * Hook for fetching available AI models
 */
export function useAIModels(): UseAIModelsReturn {
  const { data, isLoading, error, mutate } = useSWR<AIModelsResponse>(
    API_ENDPOINT,
    fetcher,
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: true,
      dedupingInterval: 60000, // Cache for 1 minute
      errorRetryCount: 3,
    }
  );

  // Extract models from response
  const models: AIModel[] = data?.data?.models ?? [];
  const defaultModel: string | null = data?.data?.default_model ?? null;
  const userPreference: string | null = data?.data?.user_preference ?? null;

  return {
    models,
    defaultModel,
    userPreference,
    isLoading,
    error: error ?? null,
    refetch: () => mutate(),
  };
}

export default useAIModels;
