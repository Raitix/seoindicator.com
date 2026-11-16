/**
 * Pricing page API hooks.
 * Task: TASK-26-20.
 *
 * The pricing page shows the credit packages together with the models
 * that can be used on them, so both lists are fetched and exposed
 * through one hook.
 */

import { useCallback, useEffect, useState } from 'react';

const PACKAGES_ENDPOINT = '/api/v1/packages';
const MODELS_ENDPOINT = '/api/v1/models';

export interface PricingPackage {
  id: number | string;
  name: string;
  credits: number;
  price: number;
  bonus_percentage?: number;
  is_popular?: boolean;
  description?: string;
}

export interface PricingModel {
  id: number | string;
  name: string;
  provider: string;
  provider_id: string;
  cost_per_analysis: number;
  is_free_tier: boolean;
  context_window: number;
  is_active: boolean;
}

/**
 * Fetch a JSON list from an endpoint.
 *
 * The list is taken from the `data` key of the response. Anything that
 * is not a list is treated as an empty one, so a malformed response
 * cannot crash the page.
 */
async function fetchList<T>(url: string): Promise<T[]> {
  const response = await fetch(url);

  if (!response.ok) {
    throw new Error(`Failed to fetch ${url} (${response.status})`);
  }

  const payload = await response.json();
  const list = Array.isArray(payload) ? payload : payload?.data;

  return Array.isArray(list) ? (list as T[]) : [];
}

export interface UsePricingListReturn<T> {
  items: T[];
  isLoading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
}

/**
 * A list fetched from an endpoint, with loading, error and refetch.
 */
function usePricingList<T>(url: string): UsePricingListReturn<T> {
  const [items, setItems] = useState<T[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  const load = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const list = await fetchList<T>(url);
      setItems(list);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Request failed'));
    } finally {
      setIsLoading(false);
    }
  }, [url]);

  useEffect(() => {
    void load();
  }, [load]);

  const refetch = useCallback(async () => {
    await load();
  }, [load]);

  return { items, isLoading, error, refetch };
}

export interface UsePackagesReturn {
  packages: PricingPackage[];
  isLoading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
}

/**
 * The credit packages shown on the pricing page.
 */
export function usePackages(): UsePackagesReturn {
  const { items, isLoading, error, refetch } =
    usePricingList<PricingPackage>(PACKAGES_ENDPOINT);

  return { packages: items, isLoading, error, refetch };
}

export interface UseModelsReturn {
  models: PricingModel[];
  isLoading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
}

/**
 * The models shown on the pricing page.
 */
export function useModels(): UseModelsReturn {
  const { items, isLoading, error, refetch } =
    usePricingList<PricingModel>(MODELS_ENDPOINT);

  return { models: items, isLoading, error, refetch };
}

export interface UsePricingPageReturn {
  packages: PricingPackage[];
  models: PricingModel[];
  isLoading: boolean;
  error: Error | null;
  refetch: () => Promise<void>;
}

/**
 * Both lists the pricing page needs.
 */
export function usePricingPage(): UsePricingPageReturn {
  const packages = usePackages();
  const models = useModels();

  const refetch = useCallback(async () => {
    await Promise.all([packages.refetch(), models.refetch()]);
  }, [packages.refetch, models.refetch]);

  return {
    packages: packages.packages,
    models: models.models,
    isLoading: packages.isLoading || models.isLoading,
    error: packages.error ?? models.error,
    refetch,
  };
}
