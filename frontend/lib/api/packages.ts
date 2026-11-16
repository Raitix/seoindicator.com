/**
 * Credit package API.
 * Task: TASK-26-20.
 *
 * The credit packages are a public endpoint, so they are fetched without
 * any credentials. The backend response is normalised here so the UI
 * always sees the same shape regardless of which field names the
 * payload used.
 *
 * @see SPEC-026
 */

import useSWR from 'swr';

const PACKAGES_ENDPOINT = '/api/v1/packages';

export interface CreditPackage {
  id: string | number;
  name: string;
  credits: number;
  price: number;
  currency: string;
  bonus: number;
  isPopular: boolean;
}

/** The raw shape the backend may return. Every field is optional. */
interface RawPackage {
  id?: unknown;
  name?: unknown;
  credits?: unknown;
  price?: unknown;
  price_usd?: unknown;
  currency?: unknown;
  bonus?: unknown;
  bonus_credits?: unknown;
  bonus_percent?: unknown;
  effective_credits?: unknown;
  isPopular?: unknown;
  is_popular?: unknown;
}

function toNumber(value: unknown, fallback = 0): number {
  if (typeof value === 'number' && Number.isFinite(value)) return value;
  if (typeof value === 'string') {
    const parsed = Number(value);
    if (Number.isFinite(parsed)) return parsed;
  }
  return fallback;
}

function toBoolean(value: unknown): boolean {
  if (typeof value === 'boolean') return value;
  if (typeof value === 'number') return value !== 0;
  if (typeof value === 'string') {
    return value === 'true' || value === '1';
  }
  return false;
}

/**
 * Normalise one package.
 *
 * The backend has used both camelCase and snake_case over time, and the
 * price has been sent as both a number and a string, so every field is
 * read defensively.
 */
export function normalizePackage(raw: RawPackage): CreditPackage {
  const bonusRaw = raw.bonus ?? raw.bonus_credits ?? raw.bonus_percent;

  return {
    id: (raw.id as string | number) ?? '',
    name: typeof raw.name === 'string' ? raw.name : '',
    credits: toNumber(raw.credits ?? raw.effective_credits),
    price: toNumber(raw.price ?? raw.price_usd),
    currency: typeof raw.currency === 'string' && raw.currency ? raw.currency : 'USD',
    bonus: toNumber(bonusRaw),
    isPopular: toBoolean(raw.isPopular ?? raw.is_popular),
  };
}

/**
 * Fetch the credit packages.
 */
export async function fetchPackages(): Promise<CreditPackage[]> {
  const response = await fetch(PACKAGES_ENDPOINT, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  });

  if (!response.ok) {
    // The backend's message is more useful than the status code, so it
    // is used when there is one.
    let message = `Failed to fetch packages (${response.status})`;
    try {
      const data = await response.json();
      if (data && typeof data.message === 'string' && data.message) {
        message = data.message;
      } else if (data && typeof data.error === 'string' && data.error) {
        message = data.error;
      }
    } catch {
      // The body was not JSON - keep the status-based message.
    }
    throw new Error(message);
  }

  const payload = await response.json();

  // The endpoint returns { data: [...] }, but a bare array is accepted
  // too. Anything else means there is nothing to show.
  const list = Array.isArray(payload) ? payload : payload?.data;
  if (!Array.isArray(list)) {
    return [];
  }

  return list.map((item: RawPackage) => normalizePackage(item ?? {}));
}

export interface UsePackagesReturn {
  packages: CreditPackage[];
  isLoading: boolean;
  error: Error | null;
  mutate: () => Promise<CreditPackage[] | undefined>;
}

/**
 * The credit packages, cached by SWR.
 */
export function usePackages(): UsePackagesReturn {
  const { data, isLoading, error, mutate } = useSWR<CreditPackage[], Error>(
    PACKAGES_ENDPOINT,
    async () => {
      const packages = await fetchPackages();
      return packages;
    }
  );

  return {
    packages: data ?? [],
    isLoading,
    error: error ?? null,
    mutate,
  };
}
