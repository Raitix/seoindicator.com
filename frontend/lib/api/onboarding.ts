/**
 * API functions for the User Onboarding feature (FEAT-017)
 */

import useSWR, { mutate as globalMutate } from "swr";

import type {
  UserOnboarding,
  OnboardingProgressResponse,
  OnboardingStartResponse,
  OnboardingStepPayload,
  OnboardingCompleteResponse,
  OnboardingSkipResponse,
  OnboardingErrorResponse,
} from "@/lib/types/onboarding";

const API_BASE = "/api/v1";

// Normalize API response to consistent format
const normalizeOnboarding = (data: any): UserOnboarding => ({
  id: data.id,
  currentStep: data.current_step ?? data.currentStep ?? 1,
  completedSteps: data.completed_steps ?? data.completedSteps ?? [],
  websiteUrl: data.website_url ?? data.websiteUrl ?? null,
  firstKeyword: data.first_keyword ?? data.firstKeyword ?? null,
  keywordId: data.keyword_id ?? data.keywordId ?? null,
  alertPreferences: data.alert_preferences ?? data.alertPreferences ?? null,
  completedAt: data.completed_at ?? data.completedAt ?? null,
  progressPercentage: data.progress_percentage ?? data.progressPercentage ?? 0,
});

// Fetcher for SWR
const fetcher = async (url: string): Promise<UserOnboarding | null> => {
  const response = await fetch(url, {
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (!response.ok) {
    if (response.status === 401) {
      return null; // Not authenticated
    }
    throw new Error(`Failed to fetch onboarding: ${response.status}`);
  }

  const json: OnboardingProgressResponse = await response.json();
  
  if (!json.success || !json.data) {
    return null;
  }

  return normalizeOnboarding(json.data);
};

/**
 * Hook to fetch the user's onboarding progress
 */
export function useOnboarding() {
  const { data, error, isLoading, mutate } = useSWR<UserOnboarding | null>(
    `${API_BASE}/onboarding`,
    fetcher,
    {
      revalidateOnFocus: false,
      revalidateOnReconnect: false,
    }
  );

  return {
    onboarding: data,
    isLoading,
    error: error?.message ?? null,
    mutate,
  };
}

/**
 * Start or resume onboarding
 */
export async function startOnboarding(): Promise<UserOnboarding> {
  const response = await fetch(`${API_BASE}/onboarding`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({}),
  });

  const json: OnboardingStartResponse | OnboardingErrorResponse = await response.json();

  if (!response.ok || !json.success) {
    const error = json as OnboardingErrorResponse;
    throw new Error(error.message || "Failed to start onboarding");
  }

  const data = json as OnboardingStartResponse;
  return normalizeOnboarding(data.data);
}

/**
 * Update an onboarding step
 */
export async function updateOnboardingStep(
  step: number,
  data: OnboardingStepPayload["data"]
): Promise<UserOnboarding> {
  const response = await fetch(`${API_BASE}/onboarding`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ step, data }),
  });

  const json: OnboardingStartResponse | OnboardingErrorResponse = await response.json();

  if (!response.ok || !json.success) {
    const error = json as OnboardingErrorResponse;
    throw new Error(error.message || "Failed to update onboarding step");
  }

  const result = json as OnboardingStartResponse;
  return normalizeOnboarding(result.data);
}

/**
 * Complete onboarding
 */
export async function completeOnboarding(): Promise<{ completedAt: string; keywordId: number | null }> {
  const response = await fetch(`${API_BASE}/onboarding/complete`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  const json: OnboardingCompleteResponse | OnboardingErrorResponse = await response.json();

  if (!response.ok || !json.success) {
    const error = json as OnboardingErrorResponse;
    throw new Error(error.message || "Failed to complete onboarding");
  }

  const data = json as OnboardingCompleteResponse;
  return {
    completedAt: data.data.completed_at,
    keywordId: data.data.keyword_id,
  };
}

/**
 * Skip onboarding
 */
export async function skipOnboarding(): Promise<string> {
  const response = await fetch(`${API_BASE}/onboarding/skip`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
    },
  });

  const json: OnboardingSkipResponse | OnboardingErrorResponse = await response.json();

  if (!response.ok || !json.success) {
    const error = json as OnboardingErrorResponse;
    throw new Error(error.message || "Failed to skip onboarding");
  }

  const data = json as OnboardingSkipResponse;
  return data.data.skipped_at;
}

/**
 * Mutate onboarding cache
 */
export async function mutateOnboarding(data?: UserOnboarding | null) {
  await globalMutate(`${API_BASE}/onboarding`, data);
}
